/**
 * WebSocket Service
 * Real-time communication with automatic reconnection
 * Replaces polling for better performance
 */

export type WebSocketMessage = {
  type: string;
  data: any;
  timestamp?: number;
};

export type WebSocketCallback = (message: WebSocketMessage) => void;

export interface WebSocketConfig {
  url: string;
  reconnectInterval?: number;
  maxReconnectAttempts?: number;
  heartbeatInterval?: number;
  debug?: boolean;
}

export class WebSocketService {
  private ws: WebSocket | null = null;
  private config: Required<WebSocketConfig>;
  private reconnectAttempts = 0;
  private reconnectTimeout: NodeJS.Timeout | null = null;
  private heartbeatInterval: NodeJS.Timeout | null = null;
  private messageHandlers: Map<string, Set<WebSocketCallback>> = new Map();
  private isIntentionallyClosed = false;

  constructor(config: WebSocketConfig) {
    this.config = {
      url: config.url,
      reconnectInterval: config.reconnectInterval ?? 3000,
      maxReconnectAttempts: config.maxReconnectAttempts ?? 5,
      heartbeatInterval: config.heartbeatInterval ?? 30000,
      debug: config.debug ?? false,
    };
  }

  /**
   * Connect to WebSocket server
   */
  connect(): void {
    if (this.ws?.readyState === WebSocket.OPEN) {
      this.log('Already connected');
      return;
    }

    this.isIntentionallyClosed = false;
    this.log('Connecting to', this.config.url);

    try {
      this.ws = new WebSocket(this.config.url);
      this.setupEventHandlers();
    } catch (error) {
      this.log('Connection error:', error);
      this.scheduleReconnect();
    }
  }

  /**
   * Disconnect from WebSocket server
   */
  disconnect(): void {
    this.isIntentionallyClosed = true;
    this.clearReconnectTimeout();
    this.clearHeartbeat();

    if (this.ws) {
      this.ws.close();
      this.ws = null;
    }

    this.log('Disconnected');
  }

  /**
   * Send message to server
   */
  send(type: string, data: any): void {
    if (!this.isConnected()) {
      this.log('Cannot send message: not connected');
      return;
    }

    const message: WebSocketMessage = {
      type,
      data,
      timestamp: Date.now(),
    };

    this.ws!.send(JSON.stringify(message));
    this.log('Sent message:', message);
  }

  /**
   * Subscribe to specific message type
   */
  on(type: string, callback: WebSocketCallback): () => void {
    if (!this.messageHandlers.has(type)) {
      this.messageHandlers.set(type, new Set());
    }

    this.messageHandlers.get(type)!.add(callback);

    // Return unsubscribe function
    return () => {
      this.off(type, callback);
    };
  }

  /**
   * Unsubscribe from message type
   */
  off(type: string, callback: WebSocketCallback): void {
    const handlers = this.messageHandlers.get(type);
    if (handlers) {
      handlers.delete(callback);
      if (handlers.size === 0) {
        this.messageHandlers.delete(type);
      }
    }
  }

  /**
   * Check if connected
   */
  isConnected(): boolean {
    return this.ws?.readyState === WebSocket.OPEN;
  }

  /**
   * Get connection state
   */
  getState(): 'CONNECTING' | 'OPEN' | 'CLOSING' | 'CLOSED' {
    if (!this.ws) return 'CLOSED';

    switch (this.ws.readyState) {
      case WebSocket.CONNECTING:
        return 'CONNECTING';
      case WebSocket.OPEN:
        return 'OPEN';
      case WebSocket.CLOSING:
        return 'CLOSING';
      case WebSocket.CLOSED:
        return 'CLOSED';
      default:
        return 'CLOSED';
    }
  }

  /**
   * Setup WebSocket event handlers
   */
  private setupEventHandlers(): void {
    if (!this.ws) return;

    this.ws.onopen = () => {
      this.log('Connected');
      this.reconnectAttempts = 0;
      this.startHeartbeat();

      // Notify connection established
      this.notifyHandlers('connection', { status: 'connected' });
    };

    this.ws.onclose = (event) => {
      this.log('Disconnected', event.code, event.reason);
      this.clearHeartbeat();

      // Notify connection closed
      this.notifyHandlers('connection', { status: 'disconnected', code: event.code });

      // Attempt reconnect if not intentionally closed
      if (!this.isIntentionallyClosed) {
        this.scheduleReconnect();
      }
    };

    this.ws.onerror = (error) => {
      this.log('WebSocket error:', error);

      // Notify error
      this.notifyHandlers('error', { error });
    };

    this.ws.onmessage = (event) => {
      try {
        const message: WebSocketMessage = JSON.parse(event.data);
        this.log('Received message:', message);

        // Handle heartbeat/pong
        if (message.type === 'pong') {
          return;
        }

        // Notify specific handlers
        this.notifyHandlers(message.type, message.data);

        // Notify wildcard handlers
        this.notifyHandlers('*', message);
      } catch (error) {
        this.log('Error parsing message:', error);
      }
    };
  }

  /**
   * Notify all handlers for a message type
   */
  private notifyHandlers(type: string, data: any): void {
    const handlers = this.messageHandlers.get(type);
    if (handlers) {
      handlers.forEach((handler) => {
        try {
          handler({ type, data });
        } catch (error) {
          this.log('Error in message handler:', error);
        }
      });
    }
  }

  /**
   * Schedule reconnection attempt
   */
  private scheduleReconnect(): void {
    if (this.reconnectAttempts >= this.config.maxReconnectAttempts) {
      this.log('Max reconnect attempts reached');
      this.notifyHandlers('connection', { status: 'failed' });
      return;
    }

    this.reconnectAttempts++;

    // Exponential backoff
    const delay = Math.min(
      this.config.reconnectInterval * Math.pow(2, this.reconnectAttempts - 1),
      30000
    );

    this.log(`Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`);

    this.reconnectTimeout = setTimeout(() => {
      this.connect();
    }, delay);
  }

  /**
   * Clear reconnect timeout
   */
  private clearReconnectTimeout(): void {
    if (this.reconnectTimeout) {
      clearTimeout(this.reconnectTimeout);
      this.reconnectTimeout = null;
    }
  }

  /**
   * Start heartbeat to keep connection alive
   */
  private startHeartbeat(): void {
    this.clearHeartbeat();

    this.heartbeatInterval = setInterval(() => {
      if (this.isConnected()) {
        this.send('ping', {});
      }
    }, this.config.heartbeatInterval);
  }

  /**
   * Clear heartbeat interval
   */
  private clearHeartbeat(): void {
    if (this.heartbeatInterval) {
      clearInterval(this.heartbeatInterval);
      this.heartbeatInterval = null;
    }
  }

  /**
   * Debug logging
   */
  private log(...args: any[]): void {
    if (this.config.debug) {
      console.log('[WebSocket]', ...args);
    }
  }
}

/**
 * React Hook for WebSocket
 */
import { useEffect, useRef, useState } from 'react';

export function useWebSocket(config: WebSocketConfig) {
  const wsRef = useRef<WebSocketService | null>(null);
  const [isConnected, setIsConnected] = useState(false);
  const [lastMessage, setLastMessage] = useState<WebSocketMessage | null>(null);

  useEffect(() => {
    // Create WebSocket service
    wsRef.current = new WebSocketService(config);

    // Subscribe to connection status
    const unsubscribeConnection = wsRef.current.on('connection', (message) => {
      setIsConnected(message.data.status === 'connected');
    });

    // Subscribe to all messages
    const unsubscribeAll = wsRef.current.on('*', (message) => {
      setLastMessage(message);
    });

    // Connect
    wsRef.current.connect();

    // Cleanup
    return () => {
      unsubscribeConnection();
      unsubscribeAll();
      wsRef.current?.disconnect();
    };
  }, [config.url]);

  const send = (type: string, data: any) => {
    wsRef.current?.send(type, data);
  };

  const subscribe = (type: string, callback: WebSocketCallback) => {
    return wsRef.current?.on(type, callback) ?? (() => {});
  };

  return {
    isConnected,
    lastMessage,
    send,
    subscribe,
    ws: wsRef.current,
  };
}

/**
 * Example usage:
 *
 * // In a component
 * const { isConnected, send, subscribe } = useWebSocket({
 *   url: 'ws://localhost:8000/ws',
 *   debug: true,
 * });
 *
 * useEffect(() => {
 *   const unsubscribe = subscribe('chat:message', (message) => {
 *     console.log('New chat message:', message.data);
 *   });
 *
 *   return unsubscribe;
 * }, [subscribe]);
 *
 * const sendMessage = () => {
 *   send('chat:message', { text: 'Hello!' });
 * };
 */
